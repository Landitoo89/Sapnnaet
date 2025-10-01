<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once('tcpdf/tcpdf.php');
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

// Obtener el ID del personal de la URL
$id_pers = isset($_GET['id_pers']) ? (int)$_GET['id_pers'] : 0;
if ($id_pers === 0) {
    die("ID de personal no proporcionado para generar la constancia.");
}

// Obtener datos del empleado desde la base de datos (último registro laboral y primas)
try {
    // Obtener datos principales
    $stmt_empleado_data = $conn->prepare("
        SELECT 
            p.nombres, 
            p.apellidos, 
            p.cedula_identidad,
            dl.fecha_ingreso, 
            dl.sueldo,
            c.nombre AS cargo_nombre,
            dl.id_laboral
        FROM datos_personales p
        INNER JOIN datos_laborales dl ON p.id_pers = dl.id_pers
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        WHERE p.id_pers = ?
        ORDER BY dl.fecha_ingreso DESC
        LIMIT 1
    ");
    $stmt_empleado_data->execute([$id_pers]);
    $data = $stmt_empleado_data->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("No se encontraron datos para el empleado con ID: " . $id_pers);
    }

    // Obtener primas personalizadas
    $stmt_primas = $conn->prepare("
        SELECT nombre_prima, monto
        FROM empleado_primas_personalizadas
        WHERE id_laboral = ?
    ");
    $stmt_primas->execute([$data['id_laboral']]);
    $primas_arr = $stmt_primas->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($primas_arr)) {
        $primas_adicionales = [];
        foreach ($primas_arr as $p) {
            $primas_adicionales[] = number_format($p['monto'], 2, ',', '.') . ' Bs. de PRIMA DE ' . $p['nombre_prima'];
        }
        $primas_adicionales = implode(', ', $primas_adicionales);
    } else {
        $primas_adicionales = 'Ninguna';
    }

    // --- CONSULTA DE QUIÉN FIRMA ---
    // En la tabla cargos, el id para "Jefe(a) de Direccion del Talento Humano" es 7
    // Buscamos el registro laboral activo más reciente de ese cargo
    $stmt_jefe = $conn->prepare("
        SELECT dp.nombres, dp.apellidos
        FROM datos_laborales dl
        INNER JOIN datos_personales dp ON dl.id_pers = dp.id_pers
        WHERE dl.id_cargo = 7 AND dl.estado = 'activo'
        ORDER BY dl.fecha_ingreso DESC
        LIMIT 1
    ");
    $stmt_jefe->execute();
    $jefe = $stmt_jefe->fetch(PDO::FETCH_ASSOC);
    $nombre_jefe = $jefe ? $jefe['nombres'] . ' ' . $jefe['apellidos'] : 'Jefe(a) de Direccion del Talento Humano';

} catch (PDOException $e) {
    die("Error de base de datos al obtener datos del empleado: " . $e->getMessage());
}

// Preparar los datos para el PDF
$nombre_completo = $data['nombres'] . ' ' . $data['apellidos'];
$cedula = $data['cedula_identidad'];
$cargo = $data['cargo_nombre'];
$fecha_ingreso = date('d/m/Y', strtotime($data['fecha_ingreso']));
$sueldo = $data['sueldo'];
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

// --- TCPDF config ---
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SAPNNAET');
$pdf->SetTitle('Constancia de Trabajo');
$pdf->SetSubject('Constancia de Trabajo');
$pdf->SetKeywords('Constancia, Trabajo, SAPNNAET');

// Quitar cabecera y pie por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// UNA SOLA PÁGINA
$pdf->AddPage();

// Encabezado
$pdf->Image('img/logo-gob.png', 10, 8, 30);
$pdf->Image('img/logo-sapnna.png', $pdf->getPageWidth() - 40, 8, 30);

$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'REPÚBLICA BOLIVARIANA DE VENEZUELA', 0, 1, 'C', false);
$pdf->Cell(0, 5, 'GOBIERNO SOCIALISTA DEL ESTADO TRUJILLO', 0, 1, 'C', false);
$pdf->Cell(0, 5, 'SERVICIO ADMINISTRATIVO DE PROTECCIÓN DEL NIÑO,', 0, 1, 'C', false);
$pdf->Cell(0, 5, 'NIÑA Y ADOLESCENTE', 0, 1, 'C', false);
$pdf->Cell(0, 5, 'SAPNNAET', 0, 1, 'C', false);
$pdf->Cell(0, 5, 'DIRECCIÓN DE TALENTO HUMANO', 0, 1, 'C', false);
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'CONSTANCIA DE TRABAJO', 0, 1, 'C', false);
$pdf->Ln(10);

// Cuerpo principal
$pdf->SetX(15);

$pdf->SetFont('helvetica', '', 12);
$cuerpo = "Quien Suscribe, $nombre_jefe, Jefe (E) de la Dirección de Talento Humano del Servicio Administrativo de Protección del Niño, Niña y Adolescente del Estado Trujillo (SAPNNAET), por medio de la presente hace constar que el(la) ciudadano(a): <b>$nombre_completo</b>, titular de la Cédula de Identidad N° <b>$cedula</b>, presta sus servicios en esta Institución como: <b>$cargo</b>, desde el día: <b>$fecha_ingreso</b>. Devengando un sueldo mensual de: <b>$sueldo_texto (Bs. ".number_format($sueldo,2,',','.').")</b>, más las siguientes primas: <b>$primas_adicionales.</b>";
$pdf->writeHTMLCell(0, 0, 15, '', $cuerpo, 0, 1, false, true, 'J', true);

$pdf->Ln(10);

// Despedida
$pdf->SetFont('helvetica', '', 12);
$despedida = "Constancia que se expide a petición de parte interesada en Valera, a los <b>$dia_reporte</b> días del mes de <b>$mes_reporte</b> de <b>$anio_reporte</b>.";
$pdf->writeHTMLCell(0, 0, 15, '', $despedida, 0, 1, false, true, 'J', true);
$pdf->Ln(17);

// Firma
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 5, 'Bolivarianamente,', 0, 1, 'C');
$pdf->Ln(25);
$line_width = 80;
$x_center_line = ($pdf->getPageWidth() - $line_width) / 2;
$pdf->SetX($x_center_line);
$pdf->Cell($line_width, 0, '', 'B', 1, 'C');
$pdf->Ln(3);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 5, $nombre_jefe, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 5, 'Jefe (E) de la Dirección de Talento Humano', 0, 1, 'C');

// Pie de página manual
$pdf->SetY(-30);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Cell(0, 4, 'Sector Agua Clara via Mendoza Fria, Complejo Carmania ...', 0, 1, 'C');
$pdf->Cell(0, 4, 'Municipio Valera, Estado Trujillo. Telefono (0271) 2318461', 0, 1, 'C');
$pdf->SetY(-20);

ob_clean();
$pdf->Output('Constancia_' . $cedula . '.pdf', 'I');