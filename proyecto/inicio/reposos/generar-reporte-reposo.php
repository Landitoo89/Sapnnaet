<?php
session_start();

// Habilitar la visualización de errores para depuración.
// ¡Asegúrate de DESHABILITAR esto en un entorno de producción por seguridad!
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ruta al archivo fpdf.php
require('fpdf/fpdf.php'); 

// Ruta al archivo conexion.php
require('../conexion.php'); 

// Clase extendida de FPDF para personalizar el encabezado y pie de página
class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        // Logo izquierda (Gobierno Bolivariano)
        // La ruta 'img/logo-gob.jpg' asume que la carpeta 'img' está en la misma ubicación que este script.
        // Si este archivo está en 'reposos/', y 'img/' está en la raíz, la ruta debería ser '../img/logo-gob.jpg'
        $this->Image('img/logo-gob.png', 10, 8, 30);  // X, Y, Ancho. Ajusta según sea necesario.

        // Logo derecha (SAPNNA)
        $this->Image('img/logo-sapnna.png', $this->GetPageWidth() - 40, 8, 30); // X, Y, Ancho. Ajusta según sea necesario.
        
        // Texto del encabezado en cursiva
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 5, mb_convert_encoding('REPÚBLICA BOLIVARIANA DE VENEZUELA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('GOBIERNO SOCIALISTA DEL ESTADO TRUJILLO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('SERVICIO ADMINISTRATIVO DE PROTECCIÓN DEL NIÑO,', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('NIÑA Y ADOLESCENTE', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('SAPNNAET', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); 
        $this->Cell(0, 5, mb_convert_encoding('DIRECCIÓN DE TALENTO HUMANO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(5); // Salto de línea

        // Título "REPOSO" en negrita (excepción a la cursiva)
        $this->SetFont('Arial', 'B', 16); 
        $this->Cell(0, 10, mb_convert_encoding('REPORTE DE REPOSO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(10); // Salto de línea después del título
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-25); // Posición a 25 mm del final para dejar espacio para la dirección y el número de página
        $this->SetFont('Arial', 'I', 9); // Fuente más pequeña para la dirección
        $this->Cell(0, 4, mb_convert_encoding('Sector Agua Clara via Mendoza Fria, Complejo Carmania ...', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 4, mb_convert_encoding('Municipio Valera, Estado Trujillo. Telefono (0271) 2318461', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
    
    }
}

// Obtener el ID del reposo de la URL
$id_reposo = isset($_GET['id_reposo']) ? (int)$_GET['id_reposo'] : 0;

if ($id_reposo === 0) {
    die("ID de reposo no proporcionado para generar el reporte.");
}

// Obtener datos del reposo y del empleado desde la base de datos
try {
    $stmt_reposo_data = $conn->prepare("
        SELECT 
            r.id_reposo,
            r.fecha_inicio,
            r.fecha_fin,
            r.tipo_concesion,
            r.motivo_reposo,
            r.dias_otorgados,
            r.estado AS estado_reposo,
            r.observaciones,
            r.ruta_archivo_adjunto,
            dp.nombres, 
            dp.apellidos, 
            dp.cedula_identidad,
            c.nombre AS cargo_nombre
        FROM reposos r
        INNER JOIN datos_personales dp ON r.id_pers = dp.id_pers
        INNER JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        WHERE r.id_reposo = ?
    ");
    $stmt_reposo_data->execute([$id_reposo]);
    $data = $stmt_reposo_data->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("No se encontraron datos para el reposo con ID: " . $id_reposo);
    }

} catch (PDOException $e) {
    die("Error de base de datos al obtener datos del reposo: " . $e->getMessage());
}

// Preparar los datos para el PDF
$nombre_completo = $data['nombres'] . ' ' . $data['apellidos'];
$cedula = $data['cedula_identidad'];
$cargo = $data['cargo_nombre'];
$fecha_inicio = date('d/m/Y', strtotime($data['fecha_inicio']));
$fecha_fin = date('d/m/Y', strtotime($data['fecha_fin']));
$dias_otorgados = $data['dias_otorgados'];
$tipo_concesion = ucfirst($data['tipo_concesion']); // Capitalizar
$motivo_reposo = $data['motivo_reposo'];
$estado_reposo = ucfirst($data['estado_reposo']); // Capitalizar
$observaciones = !empty($data['observaciones']) ? $data['observaciones'] : 'N/A';
$ruta_archivo_adjunto = $data['ruta_archivo_adjunto'];

// Fecha actual para el reporte
$fecha_reporte_dt = new DateTime();
$dia_reporte = $fecha_reporte_dt->format('d');
$mes_reporte = $fecha_reporte_dt->format('F'); // Nombre completo del mes
$anio_reporte = $fecha_reporte_dt->format('Y');

// Mapeo de nombres de meses en inglés a español
$meses_espanol = array(
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
    'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
    'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
);
$mes_reporte = $meses_espanol[$mes_reporte];


$pdf = new PDF();
$pdf->AliasNbPages(); // Necesario para {nb} en el pie de página
$pdf->AddPage();

// Definir la sangría (por ejemplo, 10 mm)
$sangria = 10; 

// Por defecto, todo el texto del cuerpo será cursiva
$pdf->SetFont('Arial', 'I', 12);

// Información del ciudadano
$pdf->Cell(0, 7, mb_convert_encoding('Ciudadano :', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el nombre
$pdf->Cell(0, 7, mb_convert_encoding($nombre_completo, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');

// Cédula
$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva para la etiqueta
$pdf->Write(7, mb_convert_encoding('C . I . N ° : ', 'ISO-8859-1', 'UTF-8')); // Escribir la etiqueta
$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para la cédula
$pdf->Write(7, $cedula); // Escribir la cédula
$pdf->Ln(7); // Salto de línea después de la cédula

// Cargo
$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el cargo
$pdf->Cell(0, 7, mb_convert_encoding($cargo, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Cell(0, 7, mb_convert_encoding('Presente . -', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(10); // Espacio

// Cuerpo del documento
$pdf->SetFont('Arial', 'I', 12); // Asegurar cursiva al inicio del párrafo
$pdf->SetX($pdf->GetX() + $sangria); // Aplicar sangría al inicio del párrafo
$pdf->Write(7, mb_convert_encoding('Por medio de la presente, se autoriza la ausencia al ciudadano(a) ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el nombre
$pdf->Write(7, mb_convert_encoding($nombre_completo, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' con C.I. N° ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para la cédula
$pdf->Write(7, $cedula);

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(', por un lapso de ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para los días
$pdf->Write(7, mb_convert_encoding($dias_otorgados, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' día(s) hábiles, con inicio el ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para fecha inicio
$pdf->Write(7, mb_convert_encoding($fecha_inicio, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' y culminación el ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para fecha fin
$pdf->Write(7, mb_convert_encoding($fecha_fin, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding('. El motivo del reposo es: ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para motivo
$pdf->Write(7, mb_convert_encoding($motivo_reposo, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding('. Tipo de concesión: ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para tipo concesion
$pdf->Write(7, mb_convert_encoding($tipo_concesion, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding('. Estado actual del reposo: ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para estado reposo
$pdf->Write(7, mb_convert_encoding($estado_reposo, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding('.', 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(15); // Salto de línea después del párrafo

// Despedida
$pdf->SetFont('Arial', 'I', 12); // Asegurar cursiva para la despedida
$pdf->SetX($pdf->GetX() + $sangria); // Aplicar sangría al inicio del párrafo de despedida
$texto_despedida_completo = 
    mb_convert_encoding('Sin más a que hacer referencia se despide de usted, a los ', 'ISO-8859-1', 'UTF-8');

$pdf->Write(7, $texto_despedida_completo);

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el día del reporte
$pdf->Write(7, $dia_reporte);

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' dias del mes de ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el mes del reporte
$pdf->Write(7, mb_convert_encoding($mes_reporte, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' de ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el año del reporte
$pdf->Write(7, $anio_reporte . '.');
$pdf->Ln(7); // Salto de línea después de la despedida para asegurar espacio

// Sección de firma
$pdf->Ln(15); 
$pdf->SetFont('Arial', 'I', 12); 
$pdf->Cell(0, 5, mb_convert_encoding('Bolivarianamente,', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); 

// Línea para la firma
$pdf->Ln(30); 
$line_width = 80; 
$x_center_line = ($pdf->GetPageWidth() - $line_width) / 2; 
$pdf->SetX($x_center_line); 
$pdf->Cell($line_width, 0, '', 'B', 1, 'C'); 
$pdf->Ln(5); 

$pdf->SetFont('Arial', 'BI', 12); 
$pdf->Cell(0, 5, mb_convert_encoding('Abog. Lucy M. Montilla Macias', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); 
$pdf->SetFont('Arial', 'I', 12); 
$pdf->Cell(0, 5, mb_convert_encoding('Jefe (A) de la Dirección de Talento Humano', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); 


// Limpiar el búfer de salida antes de enviar el PDF
ob_clean(); 
$pdf->Output('I', 'Reporte_Reposo_' . $cedula . '.pdf'); // 'I' para mostrar en el navegador, 'D' para descargar
?>
