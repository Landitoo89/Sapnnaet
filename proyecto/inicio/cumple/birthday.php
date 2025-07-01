<?php
require_once 'conexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
date_default_timezone_set('America/Caracas');

try {
    // Validar parámetros de fecha
    $start = isset($_GET['start']) ? new DateTime($_GET['start']) : new DateTime();
    $end = isset($_GET['end']) ? new DateTime($_GET['end']) : (clone $start)->modify('+1 month');
    
    $stmt = $pdo->query("SELECT nombre, apellido, fecha_nacimiento FROM datospersonales");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $eventos = [];
    foreach($empleados as $empleado) {
        // Sanitizar datos
        $nombre = htmlspecialchars($empleado['nombre'], ENT_QUOTES, 'UTF-8');
        $apellido = htmlspecialchars($empleado['apellido'], ENT_QUOTES, 'UTF-8');
        $fecha_nac = new DateTime($empleado['fecha_nacimiento']);
        
        // Calcular edad precisa
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac)->y;
        if($hoy < $fecha_nac->modify("+$edad years")) $edad--;
        
        // Generar eventos recurrentes para el rango solicitado
        $year_start = (int)$start->format('Y');
        $year_end = (int)$end->format('Y');
        
        for($year = $year_start; $year <= $year_end; $year++) {
            try {
                $event_date = new DateTime("$year-" . $fecha_nac->format('m-d'));
            } catch(Exception $e) {
                // Manejar 29 de febrero en años no bisiestos
                $event_date = new DateTime("$year-03-01");
            }
            
            if($event_date >= $start && $event_date <= $end) {
                $eventos[] = [
                    'title' => "$nombre $apellido",
                    'start' => $event_date->format('Y-m-d'),
                    'color' => '#FF6B6B',
                    'extendedProps' => [
                        'edad' => $edad + ($year - (int)$hoy->format('Y'))
                    ]
                ];
            }
        }
    }

    echo json_encode($eventos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en el servidor',
        'detalle' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Solicitud inválida',
        'detalle' => $e->getMessage()
    ]);
}
?>