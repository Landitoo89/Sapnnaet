<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');

// Función para centrado horizontal
function calcularCentroX($texto, $tamanoFuente, $fuente) {
    $bbox = imagettfbbox($tamanoFuente, 0, $fuente, $texto);
    return (1587 - ($bbox[2] - $bbox[0])) / 2;
}

$meses = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];

try {
    $idPers = $_GET['id_pers'] ?? null;
    if (!$idPers) throw new Exception("ID no proporcionado");

    require_once 'conexion.php';
    
    $stmt = $pdo->prepare("
        SELECT 
            p.nombres,
            p.apellidos,
            p.fecha_nacimiento
        FROM datos_personales p
        WHERE p.id_pers = ?
    ");
    $stmt->execute([$idPers]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) throw new Exception("Empleado no encontrado");

    // Procesar fecha
    $fecha = DateTime::createFromFormat('Y-m-d', $empleado['fecha_nacimiento']);
    if (!$fecha) throw new Exception("Fecha de nacimiento inválida");
    $fechaTexto = $fecha->format('d') . ' de ' . $meses[(int)$fecha->format('m')];

    // Cargar plantilla
    $plantillaPath = __DIR__ . '/templates/plantilla_cumple.jpg';
    if (!file_exists($plantillaPath)) throw new Exception("No se encuentra la plantilla: $plantillaPath");
    $imagen = imagecreatefromjpeg($plantillaPath);
    if (!$imagen) throw new Exception("No se pudo cargar la imagen de plantilla.");

    // Configuración
    $colorPrincipal = imagecolorallocate($imagen, 41, 128, 185);
    $colorSecundario = imagecolorallocate($imagen, 85, 85, 85);
    $fuente = __DIR__ . '/fonts/Oldenburg-Regular.ttf';
    if (!file_exists($fuente)) throw new Exception("No se encuentra la fuente: $fuente");

    // Apellidos
    $textoApellidos = $empleado['apellidos'];
    $tamanoApellidos = 80;
    $bbox = imagettfbbox($tamanoApellidos, 0, $fuente, $textoApellidos);
    $anchoTexto = $bbox[2] - $bbox[0];
    $xApellidos = (1587 / 2) - ($anchoTexto / 2); 
    
    imagettftext(
        $imagen, 
        $tamanoApellidos, 
        0, 
        $xApellidos,  // X calculado
        845,          // Y fijo
        $colorSecundario, 
        $fuente, 
        $textoApellidos
    );

    // Nombres
    $textoNombre = $empleado['nombres'];
    $tamanoNombre = 80;
    $bbox = imagettfbbox($tamanoNombre, 0, $fuente, $textoNombre);
    $xNombre = (1587 / 2) - (($bbox[2] - $bbox[0]) / 2);
    
    imagettftext(
        $imagen, 
        $tamanoNombre, 
        0, 
        $xNombre,   // X calculado
        695,        // Y fijo
        $colorPrincipal, 
        $fuente, 
        $textoNombre
    );

    // Fecha (opcional, esquina inferior derecha)
    /*
    $bboxFecha = imagettfbbox(40, 0, $fuente, $fechaTexto);
    $xFecha = 1587 - ($bboxFecha[2] - $bboxFecha[0]) - 200;
    imagettftext(
        $imagen, 
        40, 
        0, 
        $xFecha,
        2200,
        $colorSecundario, 
        $fuente, 
        $fechaTexto
    );
    */

    $nombreDescarga = 'cumple_' . preg_replace('/\s+/', '_', $empleado['nombres']) . '_' . $idPers . '.jpg';
    
    // Limpiamos cualquier output anterior
    if (ob_get_length()) ob_clean();

    // Headers para forzar descarga
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
    header('Cache-Control: max-age=0');
    
    imagejpeg($imagen, null, 90);
    imagedestroy($imagen);
    exit;

} catch (Exception $e) {
    // Limpiar buffers y manejar errores
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain');
    http_response_code(500);
    die("ERROR: " . $e->getMessage());
}
?>