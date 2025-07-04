<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require('fpdf/fpdf.php');
require('../conexion.php');

// Función para convertir números a texto (para el sueldo)
function numeroATexto($num) {
    $entero = floor($num);
    $decimal = round(($num - $entero) * 100);

    $unidades = array(
        0 => 'CERO', 1 => 'UN', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO', 5 => 'CINCO',
        6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE', 10 => 'DIEZ', 11 => 'ONCE',
        12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE', 16 => 'DIECISÉIS',
        17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE', 20 => 'VEINTE',
        30 => 'TREINTA', 40 => 'CUARENTA', 50 => 'CINCUENTA', 60 => 'SESENTA',
        70 => 'SETENTA', 80 => 'OCHENTA', 90 => 'NOVENTA'
    );

    $decenas = array(
        2 => 'VEINTI', 3 => 'TREINTA Y ', 4 => 'CUARENTA Y ', 5 => 'CINCUENTA Y ',
        6 => 'SESENTA Y ', 7 => 'SETENTA Y ', 8 => 'OCHENTA Y ', 9 => 'NOVENTA Y '
    );

    $centenas = array(
        100 => 'CIEN', 200 => 'DOSCIENTOS', 300 => 'TRESCIENTOS', 400 => 'CUATROCIENTOS',
        500 => 'QUINIENTOS', 600 => 'SEISCIENTOS', 700 => 'SETECIENTOS', 800 => 'OCHOCIENTOS',
        900 => 'NOVECIENTOS'
    );

    $texto = '';

    if ($entero == 0) {
        $texto = 'CERO';
    } elseif ($entero < 21) {
        $texto = $unidades[$entero];
    } elseif ($entero < 100) {
        $texto = $decenas[floor($entero / 10)];
        if ($entero % 10 != 0) {
            $texto .= $unidades[$entero % 10];
        }
    } elseif ($entero < 1000) {
        if ($entero == 100) {
            $texto = 'CIEN';
        } else {
            $texto = $centenas[floor($entero / 100) * 100];
            $resto = $entero % 100;
            if ($resto > 0) {
                if ($resto < 21) {
                    $texto .= ' ' . $unidades[$resto];
                } else {
                    $texto .= ' ' . $decenas[floor($resto / 10)];
                    if ($resto % 10 != 0) {
                        $texto .= $unidades[$resto % 10];
                    }
                }
            }
        }
    } else {
        $texto = 'NÚMERO MUY GRANDE';
    }

    $texto .= ' BOLIVARES CON ' . sprintf('%02d', $decimal) . '/100';
    return $texto;
}

class PDF extends FPDF
{
    function Header()
    {
        $this->Image('img/logo-gob.png', 10, 8, 30);
        $this->Image('img/logo-sapnna.png', $this->GetPageWidth() - 40, 8, 30);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, mb_convert_encoding('REPÚBLICA BOLIVARIANA DE VENEZUELA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('GOBIERNO SOCIALISTA DEL ESTADO TRUJILLO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('SERVICIO ADMINISTRATIVO DE PROTECCIÓN DEL NIÑO,', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('NIÑA Y ADOLESCENTE', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('SAPNNAET', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('DIRECCIÓN DE TALENTO HUMANO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, mb_convert_encoding('CONSTANCIA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 7);
        $this->Cell(0, 4, mb_convert_encoding('Sector Agua Clara via Mendoza Fria, Complejo Carmania ...', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 4, mb_convert_encoding('Municipio Valera, Estado Trujillo. Telefono (0271) 2318461', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Obtener el ID del personal de la URL
$id_pers = isset($_GET['id_pers']) ? (int)$_GET['id_pers'] : 0;
if ($id_pers === 0) {
    die("ID de personal no proporcionado para generar la constancia.");
}

// Obtener datos del empleado desde la base de datos
try {
    $stmt_empleado_data = $conn->prepare("
        SELECT 
            p.nombres, 
            p.apellidos, 
            p.cedula_identidad,
            dl.fecha_ingreso, 
            c.nombre AS cargo_nombre,
            c.sueldo,
            GROUP_CONCAT(CONCAT(pr.monto, ' Bs. de PRIMA DE ', pr.nombre) SEPARATOR ', ') AS primas_adicionales
        FROM datos_personales p
        INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        LEFT JOIN empleado_primas ep ON dl.id_laboral = ep.id_laboral
        LEFT JOIN primas pr ON ep.id_prima = pr.id_prima
        WHERE p.id_pers = ?
        GROUP BY p.nombres, p.apellidos, p.cedula_identidad, dl.fecha_ingreso, c.nombre, c.sueldo
    ");
    $stmt_empleado_data->execute([$id_pers]);
    $data = $stmt_empleado_data->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("No se encontraron datos para el empleado con ID: " . $id_pers);
    }
} catch (PDOException $e) {
    die("Error de base de datos al obtener datos del empleado: " . $e->getMessage());
}

// Preparar los datos para el PDF
$nombre_completo = $data['nombres'] . ' ' . $data['apellidos'];
$cedula = $data['cedula_identidad'];
$cargo = $data['cargo_nombre'];
$fecha_ingreso = date('d/m/Y', strtotime($data['fecha_ingreso']));
$sueldo = $data['sueldo'];
$primas_adicionales = !empty($data['primas_adicionales']) ? $data['primas_adicionales'] : 'Ninguna';

// Convertir sueldo a texto
$sueldo_texto = numeroATexto($sueldo);

// Fecha actual para el reporte
$fecha_reporte_dt = new DateTime();
$dia_reporte = $fecha_reporte_dt->format('d');
$mes_reporte = $fecha_reporte_dt->format('F');
$anio_reporte = $fecha_reporte_dt->format('Y');
$meses_espanol = array(
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
    'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
    'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
);
$mes_reporte = $meses_espanol[$mes_reporte];

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- TEXTO PRINCIPAL EN UNA SOLA LÍNEA CON NEGRITAS DONDE CORRESPONDE ---
$sangria = 15;
$pdf->SetX($sangria);

$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(
    "Quien Suscribe, Abog. Lucy M. Montilla Macias, Jefe (E) de la Dirección de Talento Humano del Servicio Administrativo de Protección del Niño, Niña y Adolescente del Estado Trujillo (SAPNNAET), por medio de la presente hace constar que el(la) ciudadano(a): ",
    'ISO-8859-1', 'UTF-8'
));

$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, mb_convert_encoding($nombre_completo, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(", titular de la Cédula de Identidad N° ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, $cedula);

$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(", presta sus servicios en esta Institución como: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, mb_convert_encoding($cargo, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(", desde el día: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, $fecha_ingreso);

$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(". Devengando un sueldo mensual de: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, mb_convert_encoding($sueldo_texto . " (Bs. " . number_format($sueldo, 2, ',', '.') . ")", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(", más las siguientes primas: ", 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, mb_convert_encoding($primas_adicionales, 'ISO-8859-1', 'UTF-8'));
$pdf->Write(7, '.');

$pdf->Ln(15);

// Despedida
$pdf->SetFont('Arial', 'I', 12);
$pdf->SetX($sangria);
$pdf->Write(7, mb_convert_encoding('Constancia que se expide a petición de parte interesada en Valera, a los ', 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, $dia_reporte);
$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(' días del mes de ', 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, mb_convert_encoding($mes_reporte, 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial', 'I', 12);
$pdf->Write(7, mb_convert_encoding(' de ', 'ISO-8859-1', 'UTF-8'));
$pdf->SetFont('Arial', 'BI', 12);
$pdf->Write(7, $anio_reporte . '.');
$pdf->Ln(15);

// Firma
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 5, mb_convert_encoding('Bolivarianamente,', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(30);
$line_width = 80;
$x_center_line = ($pdf->GetPageWidth() - $line_width) / 2;
$pdf->SetX($x_center_line);
$pdf->Cell($line_width, 0, '', 'B', 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'BI', 12);
$pdf->Cell(0, 5, mb_convert_encoding('Abog. Lucy M. Montilla Macias', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 5, mb_convert_encoding('Jefe (E) de la Dirección de Talento Humano', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

ob_clean();
$pdf->Output('I', 'Constancia_' . $cedula . '.pdf');