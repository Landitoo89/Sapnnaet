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

// Función para convertir números a palabras (para días)
function numeroATexto($num) {
    $unidades = array(
        0 => 'CERO', 1 => 'UN', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO', 5 => 'CINCO',
        6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE', 10 => 'DIEZ', 11 => 'ONCE',
        12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE', 16 => 'DIECISEIS',
        17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE', 20 => 'VEINTE',
        21 => 'VEINTIÚN', 22 => 'VEINTIDOS', 23 => 'VEINTITRES', 24 => 'VEINTICUATRO',
        25 => 'VEINTICINCO', 26 => 'VEINTISEIS', 27 => 'VEINTISIETE', 28 => 'VEINTIOCHO',
        29 => 'VEINTINUEVE', 30 => 'TREINTA', 31 => 'TREINTAIÚN',
    );
    if ($num >= 0 && $num <= 31) {
        return $unidades[$num];
    }
    return (string)$num; // Retorna el número como string si está fuera del rango simple
}

// Clase extendida de FPDF para personalizar el encabezado y pie de página (opcional, pero útil para logos)
class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        // Logo izquierda (Gobierno Bolivariano)
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

        // Título "VACACIONES" en negrita (excepción a la cursiva)
        $this->SetFont('Arial', 'B', 16); 
        $this->Cell(0, 10, mb_convert_encoding('VACACIONES', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(10); // Salto de línea después del título
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-25); // Posición a 25 mm del final para dejar espacio para la dirección y el número de página
        $this->SetFont('Arial', 'I', 10); // Fuente más pequeña para la dirección
        $this->Cell(0, 4, mb_convert_encoding('Sector Agua Clara via Mendoza Fria, Complejo Carmania ...', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 4, mb_convert_encoding('Municipio Valera, Estado Trujillo. Telefono (0271) 2318461', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        
    }
}

// Obtener el ID de la vacación de la URL
$id_vacacion = isset($_GET['id_vacacion']) ? (int)$_GET['id_vacacion'] : 0;

if ($id_vacacion === 0) {
    die("ID de vacación no proporcionado para generar el reporte.");
}

// Obtener datos de la base de datos usando el ID de la vacación
try {
    $stmt_vacacion_data = $conn->prepare("
        SELECT 
            v.id_pers,
            v.fecha_inicio,
            v.fecha_fin,
            v.estado AS estado_vacacion, -- Nuevo: Obtener el estado de la vacación
            v.vacacion_original_id,     -- Nuevo: Obtener si es una vacación interrumpida/reanudada
            dp.nombres, 
            dp.apellidos, 
            dp.cedula_identidad,
            c.nombre AS cargo_nombre,
            DATEDIFF(v.fecha_fin, v.fecha_inicio) + 1 AS dias_solicitados
        FROM vacaciones v
        INNER JOIN datos_personales dp ON v.id_pers = dp.id_pers
        INNER JOIN datos_laborales dl ON dp.id_pers = dl.id_pers
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        WHERE v.id_vacaciones = ?
    ");
    $stmt_vacacion_data->execute([$id_vacacion]);
    $data = $stmt_vacacion_data->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("No se encontraron datos para la vacación con ID: " . $id_vacacion);
    }

    // Determinar el "Período" de la vacación
    $periodo_vacaciones_str = '';
    // Si la vacación es 'interrumpida' o 'pendiente_reposo', buscar el período de la vacación original
    if ($data['estado_vacacion'] === 'interrumpida' || $data['estado_vacacion'] === 'pendiente_reposo') {
        $original_vacation_id = $data['vacacion_original_id'] ?? $id_vacacion; // Usar el propio ID si no hay original_id
        
        // Buscar las fechas originales de la vacación que fue "interrumpida" o es la "original"
        $stmt_original_vacation_dates = $conn->prepare("
            SELECT fecha_inicio, fecha_fin
            FROM vacaciones
            WHERE id_vacaciones = ? OR vacacion_original_id = ?
            ORDER BY fecha_inicio ASC LIMIT 1
        ");
        $stmt_original_vacation_dates->execute([$original_vacation_id, $original_vacation_id]);
        $original_vacation_dates = $stmt_original_vacation_dates->fetch(PDO::FETCH_ASSOC);

        if ($original_vacation_dates) {
            $year_inicio_original = date('Y', strtotime($original_vacation_dates['fecha_inicio']));
            $year_fin_original = date('Y', strtotime($original_vacation_dates['fecha_fin']));
            $periodo_vacaciones_str = ($year_inicio_original == $year_fin_original) ? $year_inicio_original : $year_inicio_original . '-' . $year_fin_original;
        } else {
             // Fallback a los años de la vacación actual si no se encuentra la original
            $year_inicio = date('Y', strtotime($data['fecha_inicio']));
            $year_fin = date('Y', strtotime($data['fecha_fin']));
            $periodo_vacaciones_str = ($year_inicio == $year_fin) ? $year_inicio : $year_inicio . '-' . $year_fin;
        }

    } else {
        // Para vacaciones normales, usar los años del registro actual
        $year_inicio = date('Y', strtotime($data['fecha_inicio']));
        $year_fin = date('Y', strtotime($data['fecha_fin']));
        $periodo_vacaciones_str = ($year_inicio == $year_fin) ? $year_inicio : $year_inicio . '-' . $year_fin;
    }


} catch (PDOException $e) {
    die("Error de base de datos al obtener datos de la vacación: " . $e->getMessage());
}

$nombre_completo = $data['nombres'] . ' ' . $data['apellidos'];
$cedula = $data['cedula_identidad'];
$cargo = $data['cargo_nombre'];
$fecha_inicio = date('d/m/Y', strtotime($data['fecha_inicio']));
$fecha_fin = date('d/m/Y', strtotime($data['fecha_fin']));
$dias_vacaciones = $data['dias_solicitados'];
$periodo_vacaciones = $periodo_vacaciones_str; // Usar el período calculado


// Calcular fecha de reincorporación
$fecha_reincorporacion_dt = new DateTime($data['fecha_fin']);
$fecha_reincorporacion_dt->modify('+1 day');
$fecha_reincorporacion = $fecha_reincorporacion_dt->format('d/m/Y');

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

// Cédula: Imprimir etiqueta y valor en la misma línea usando Write()
$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva para la etiqueta
$pdf->Write(7, mb_convert_encoding('C . I . N ° : ', 'ISO-8859-1', 'UTF-8')); // Escribir la etiqueta
$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para la cédula
$pdf->Write(7, $cedula); // Escribir la cédula
$pdf->Ln(7); // Salto de línea después de la cédula

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el cargo
$pdf->Cell(0, 7, mb_convert_encoding($cargo, 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Cell(0, 7, mb_convert_encoding('Presente . -', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(10); // Espacio

// Cuerpo del documento - Usando Write() para controlar el estilo en línea
$pdf->SetFont('Arial', 'I', 12); // Asegurar cursiva al inicio del párrafo
$pdf->SetX($pdf->GetX() + $sangria); // Aplicar sangría al inicio del párrafo
$pdf->Write(7, mb_convert_encoding('Ante todo reciba un cordial saludo Bolivariano, Revolucionario y Socialista, la presente es para notificar que la solicitud del disfrute de sus vacaciones hecha por usted, han sido APROBADAS por el lapso de ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el número de días en texto
$pdf->Write(7, mb_convert_encoding(numeroATexto($dias_vacaciones), 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' (', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el número de días en dígitos
$pdf->Write(7, $dias_vacaciones);

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(')  DÍAS  hábiles  del  periodo  ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el período de vacaciones
$pdf->Write(7, mb_convert_encoding($periodo_vacaciones, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding('  de  disfrute,  las  cuales  se  deberán  iniciar  a  partir  del  ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para la fecha de inicio
$pdf->Write(7, mb_convert_encoding($fecha_inicio, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' hasta  el ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para la fecha de fin
$pdf->Write(7, mb_convert_encoding($fecha_fin, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding(' , debiéndose  reincorporar  el  día ', 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para la fecha de reincorporación
$pdf->Write(7, mb_convert_encoding($fecha_reincorporacion, 'ISO-8859-1', 'UTF-8'));

$pdf->SetFont('Arial', 'I', 12); // Volver a cursiva
$pdf->Write(7, mb_convert_encoding('.', 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(15); // Salto de línea después del párrafo

// Despedida - Ahora alineado a la izquierda usando Write() para un flujo de texto correcto
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
$pdf->Write(7, mb_convert_encoding(' de ', 'ISO-8859-1', 'UTF-8')); // <-- Línea corregida

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva para el año del reporte
$pdf->Write(7, $anio_reporte . '.');
$pdf->Ln(7); // Salto de línea después de la despedida para asegurar espacio

// Sección de firma (ya no en el footer)
$pdf->Ln(15); // Aumentado el espacio antes de "Bolivarianamente," para separarlo más del párrafo anterior (2 líneas de 7mm cada una = 14mm)
$pdf->SetFont('Arial', 'I', 12); // Cursiva y tamaño de párrafo para "Bolivarianamente,"
$pdf->Cell(0, 5, mb_convert_encoding('Bolivarianamente,', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); // Centrado

// Línea para la firma
$pdf->Ln(30); // Aumentado el espacio antes de la línea para separarlo de "Bolivarianamente,"
$line_width = 80; // Ancho de la línea de firma
$x_center_line = ($pdf->GetPageWidth() - $line_width) / 2; // Calcular posición X para centrar
$pdf->SetX($x_center_line); // Mover el cursor a la posición central
$pdf->Cell($line_width, 0, '', 'B', 1, 'C'); // Línea horizontal para la firma (ancho de 80mm, borde inferior)
$pdf->Ln(5); // Espacio después de la línea para acercar el nombre

$pdf->SetFont('Arial', 'BI', 12); // Negrita y cursiva y tamaño de párrafo para el nombre
$pdf->Cell(0, 5, mb_convert_encoding('Abog. Lucy M. Montilla Macias', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); // Centrado
$pdf->SetFont('Arial', 'I', 12); // Cursiva y tamaño de párrafo para el cargo
$pdf->Cell(0, 5, mb_convert_encoding('Jefe (A) de la Dirección de Talento Humano', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C'); // Centrado


// Limpiar el búfer de salida antes de enviar el PDF
ob_clean(); 
$pdf->Output('I', 'Reporte_Vacacion_' . $cedula . '.pdf'); // 'I' para mostrar en el navegador, 'D' para descargar
?>
