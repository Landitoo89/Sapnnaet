<?php
require_once 'conexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Para evitar problemas de CORS
date_default_timezone_set('America/Caracas');

try {
    $start = isset($_GET['start']) ? new DateTime($_GET['start']) : new DateTime();
    $end = isset($_GET['end']) ? new DateTime($_GET['end']) : new DateTime();
    
    $stmt = $pdo->query("SELECT nombre, apellido, fecha_nacimiento FROM datospersonales");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $eventos = [];
    foreach($empleados as $empleado) {
        // Crear objeto DateTime con la fecha de nacimiento original
        $fecha_nacimiento = new DateTime($empleado['fecha_nacimiento']);
        $hoy = new DateTime();
        
        // Calcular edad exacta
        $edad = $hoy->diff($fecha_nacimiento)->y;
        
        // Crear fecha de evento para el año actual
        $fecha_evento = (new DateTime())->setDate(
            $start->format('Y'), // Año del rango solicitado
            $fecha_nacimiento->format('m'),
            $fecha_nacimiento->format('d')
        );
        
        if($fecha_evento >= $start && $fecha_evento <= $end) {
            $eventos[] = [
                'title' => $empleado['nombre'] . ' ' . $empleado['apellido'],
                'start' => $fecha_evento->format('Y-m-d'),
                'color' => '#FF6B6B',
                'extendedProps' => [
                    'edad' => $edad // Nombre en español para consistencia
                ],
                'display' => 'auto'
            ];
        }
    }
    
    echo json_encode($eventos, JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en el servidor',
        'detalle' => $e->getMessage() // Mejor en español
    ]);
}
?>