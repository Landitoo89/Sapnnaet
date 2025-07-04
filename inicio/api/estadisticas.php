<?php
require '../conexion.php';

// Establecer el tipo de vista por defecto a 'yearly' (que ahora mostrará los meses)
$type = $_GET['type'] ?? 'yearly';

$data = [
    'labels' => [],
    'datasets' => [
        'activos' => [],
        'vacaciones' => [],
        'cumpleanos' => [],
        'reposos' => []
    ],
    'title' => ''
];

try {
    if ($type === 'monthly') {
        // --- Lógica para la vista Mensual (por Día) ---
        $currentYear = date('Y');
        $currentMonth = date('m');
        $numDays = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $data['title'] = 'Estadísticas Diarias de ' . $monthNames[(int)$currentMonth] . ' ' . $currentYear;

        for ($day = 1; $day <= $numDays; $day++) {
            $data['labels'][] = 'Día ' . $day;
            $currentDate = $currentYear . '-' . $currentMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);

            // 1. Empleados activos por día
            // Consideramos activos si la fecha de ingreso es anterior o igual al día actual Y el estado es activo
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM datos_laborales 
                WHERE fecha_ingreso <= ? 
                AND estado = 'activo'
            ");
            $stmt->execute([$currentDate]);
            $data['datasets']['activos'][] = (int)$stmt->fetchColumn();

            // 2. Vacaciones vigentes por día
            // Las vacaciones que incluyen el día actual en su rango
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM vacaciones 
                WHERE ? BETWEEN fecha_inicio AND fecha_fin
            ");
            $stmt->execute([$currentDate]);
            $data['datasets']['vacaciones'][] = (int)$stmt->fetchColumn();

            // 3. Cumpleaños por día
            // Cumpleaños que caen en el día y mes actual (sin importar el año)
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM datos_personales 
                WHERE DAY(fecha_nacimiento) = ? AND MONTH(fecha_nacimiento) = ?
            ");
            $stmt->execute([$day, $currentMonth]);
            $data['datasets']['cumpleanos'][] = (int)$stmt->fetchColumn();

            // 4. Reposos vigentes por día
            // Los reposos registrados que incluyen el día actual en su rango
            // NOTA: Tu tabla `reposos` no está incluida en los snippets proporcionados,
            // asumo que tiene `fecha_inicio` y `fecha_fin`. Si no es así, ajusta la consulta.
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM reposos 
                WHERE ? BETWEEN fecha_inicio AND fecha_fin
            ");
            $stmt->execute([$currentDate]);
            $data['datasets']['reposos'][] = (int)$stmt->fetchColumn();
        }

    } elseif ($type === 'yearly') {
        // --- Lógica para la vista Anual (por Mes) ---
        $data['labels'] = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 
            'Mayo', 'Junio', 'Julio', 'Agosto', 
            'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        $data['title'] = 'Estadísticas Mensuales de Personal';
        $currentYear = date('Y');

        for ($mes = 1; $mes <= 12; $mes++) {
            // 1. Empleados activos por mes (basado en el estado al final del mes)
            // Se asume que activos se refieren a quienes estaban activos en algún momento del mes
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT dl.id_pers)
                FROM datos_laborales dl
                WHERE dl.estado = 'activo' 
                AND YEAR(dl.fecha_ingreso) <= ? AND MONTH(dl.fecha_ingreso) <= ?
            ");
            $stmt->execute([$currentYear, $mes]);
            $data['datasets']['activos'][] = (int)$stmt->fetchColumn();

            // 2. Vacaciones vigentes por mes (vacaciones que ocurren en ese mes del año actual)
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM vacaciones 
                WHERE (MONTH(fecha_inicio) = ? OR MONTH(fecha_fin) = ? OR (fecha_inicio < ? AND fecha_fin > ?))
                AND YEAR(fecha_inicio) = ?
            ");
            $endOfMonth = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $currentYear)); // Last day of the month
            $startOfMonth = date('Y-m-01', mktime(0, 0, 0, $mes, 1, $currentYear)); // First day of the month
            $stmt->execute([$mes, $mes, $startOfMonth, $endOfMonth, $currentYear]);
            $data['datasets']['vacaciones'][] = (int)$stmt->fetchColumn();

            // 3. Cumpleaños por mes (cumpleaños que caen en ese mes)
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM datos_personales 
                WHERE MONTH(fecha_nacimiento) = ?
            ");
            $stmt->execute([$mes]);
            $data['datasets']['cumpleanos'][] = (int)$stmt->fetchColumn();

            // 4. Reposos vigentes por mes (reposos que ocurren en ese mes del año actual)
            // NOTA: Tu tabla `reposos` no está incluida en los snippets proporcionados,
            // asumo que tiene `fecha_inicio` y `fecha_fin`. Si no es así, ajusta la consulta.
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM reposos 
                WHERE (MONTH(fecha_inicio) = ? OR MONTH(fecha_fin) = ? OR (fecha_inicio < ? AND fecha_fin > ?))
                AND YEAR(fecha_inicio) = ?
            ");
            $stmt->execute([$mes, $mes, $startOfMonth, $endOfMonth, $currentYear]);
            $data['datasets']['reposos'][] = (int)$stmt->fetchColumn();
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
