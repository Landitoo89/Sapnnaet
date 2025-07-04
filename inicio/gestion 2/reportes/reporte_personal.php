<?php
require 'conexion/conexion_db.php';
require_once('fpdf/fpdf.php');
session_start();

// ==== FUNCIÓN PARA REGISTRAR LOGS ====
function registrarLog($conn, $user_id, $event_type, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    // Usar prepare/execute estilo MySQLi
    $stmt = $conn->prepare("INSERT INTO action_logs (user_id, event_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $event_type, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}
$current_user_id = $_SESSION['usuario']['id'] ?? null;

// Solo permitir acceso a admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Parámetro de búsqueda opcional
$searchTerm = '';
$searchCondition = '';
$params = [];
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $searchTerm = trim($_GET['q']);
    $searchCondition = " WHERE (nombres LIKE ? OR apellidos LIKE ? OR cedula_identidad LIKE ? OR rif LIKE ? OR pasaporte LIKE ?) ";
    $searchTermLike = "%$searchTerm%";
    $params = array_fill(0, 5, $searchTermLike);
}

// Consulta
$sql = "SELECT * FROM datos_personales $searchCondition ORDER BY fecha_registro DESC";
$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// ==== LOG DE GENERACIÓN DE REPORTE ====
$log_details = "Generación de reporte PDF de datos personales";
if ($searchTerm) {
    $log_details .= " (Búsqueda: $searchTerm)";
}
registrarLog(
    $conexion,
    $current_user_id,
    'reporte_datos_personales_pdf',
    $log_details
);

// --- PDF GENERATION ---
class PDF extends FPDF
{
    // Ancho de las columnas
    private $widths;

    function Header()
    {
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,('Reporte de Datos Personales'),0,1,'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }

    function SetWidths($w)
    {
        $this->widths = $w;
    }

    function Row($data)
    {
        $nb = 0;
        // Calcular la altura máxima de la fila
        for($i=0;$i<count($data);$i++)
            $nb = max($nb,$this->NbLines($this->widths[$i],$data[$i]));
        $h = 8*$nb;
        $this->CheckPageBreak($h);
        // Dibujar las celdas de la fila
        for($i=0;$i<count($data);$i++)
        {
            $w = $this->widths[$i];
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x,$y,$w,$h);
            $this->MultiCell($w,8,$data[$i],0,'L');
            $this->SetXY($x+$w,$y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w,$txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',$txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i<$nb)
        {
            $c = $s[$i];
            if($c=="\n")
            {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep = $i;
            $l += $cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i = $sep+1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
}

// Create PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('L','A4');
$pdf->SetFont('Arial','B',10);

// Definición de anchos de columnas: ID, Nombre, Cedula, Teléfono, Correo, Nacimiento, Nacionalidad, Registrado
$widths = [12, 50, 32, 32, 55, 32, 30, 35];
$pdf->SetWidths($widths);

// Table header
$pdf->SetFillColor(44,62,80);
$pdf->SetTextColor(255,255,255);

$header = [
    'ID',
    'Nombre Completo',
    'Cédula',
    'Teléfono',
    'Correo',
    'Nacimiento',
    'Nacionalidad',
    'Registrado'
];
// Imprimir cabecera
for($i=0;$i<count($header);$i++)
    $pdf->Cell($widths[$i],10,($header[$i]),1,0,'C',true);
$pdf->Ln();

// Table body
$pdf->SetFont('Arial','',9);
$pdf->SetTextColor(0,0,0);

foreach ($data as $row) {
    $nombre = $row['nombres'].' '.$row['apellidos'];
    $cedula = $row['cedula_identidad'];
    $telefono = $row['telefono_contacto'];
    $correo = $row['correo_electronico'];
    $nac = date('d/m/Y', strtotime($row['fecha_nacimiento']));
    $registrado = date('d/m/Y H:i', strtotime($row['fecha_registro']));
    $nacionalidad = $row['nacionalidad'];

    // Prevenir celdas vacías que puedan dar problemas
    $rowdata = [
        $row['id_pers'] ?? '',
        $nombre ?? '',
        $cedula ?? '',
        $telefono ?? '',
        $correo ?? '',
        $nac ?? '',
        $nacionalidad ?? '',
        $registrado ?? ''
    ];

    // Asegurar correcto encoding para FPDF (Windows-1252)
    foreach($rowdata as $k=>$v){
        $rowdata[$k] = mb_convert_encoding($v, 'Windows-1252', 'UTF-8');
    }

    $pdf->Row($rowdata);
}

// Output PDF
$pdf->Output('I','reporte_datos_personales.pdf');
?>