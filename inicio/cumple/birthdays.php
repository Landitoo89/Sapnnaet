<?php
require_once 'conexion.php';



header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('America/Caracas');

try {
    $start = isset($_GET['start']) ? new DateTime($_GET['start']) : new DateTime();
    $end = isset($_GET['end']) ? new DateTime($_GET['end']) : new DateTime();
    
    // Incluimos el campo 'genero'
    $stmt = $pdo->query("
        SELECT 
            p.id_pers,
            p.nombres,
            p.apellidos,
            p.fecha_nacimiento,
            p.genero
        FROM datos_personales p
    ");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $eventos = [];
    foreach($empleados as $empleado) {
        $fecha_nacimiento = new DateTime($empleado['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nacimiento)->y;
        
        $fecha_evento = (new DateTime())->setDate(
            $start->format('Y'),
            $fecha_nacimiento->format('m'),
            $fecha_nacimiento->format('d')
        );
        
        if($fecha_evento >= $start && $fecha_evento <= $end) {
            $nombreParts = explode(' ', trim($empleado['nombres']));
            $apellidoParts = explode(' ', trim($empleado['apellidos']));
            
            $eventos[] = [
                'id' => $empleado['id_pers'],
                'title' => ($nombreParts[0] ?? '') . ' ' . ($apellidoParts[0] ?? ''),
                'start' => $fecha_evento->format('Y-m-d'),
                // Puedes eliminar 'color' porque ahora el color lo da el JS por género
                'extendedProps' => [
                    'edad' => $edad,
                    'nombre_completo' => $empleado['nombres'] . ' ' . $empleado['apellidos'],
                    'genero' => isset($empleado['genero']) ? mb_strtolower(trim($empleado['genero'])) : 'otro'
                ]
            ];
        }
    }
    
    echo json_encode($eventos, JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en el servidor',
        'detalle' => $e->getMessage()
    ]);
}
?>