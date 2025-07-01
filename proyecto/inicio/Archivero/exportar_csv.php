<?php
require('conexion_archivero.php');

// 1. Configurar cabeceras y BOM para Excel
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="archivos_' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF"; // BOM para UTF-8 (Solución acentos)

// 2. Obtener parámetros de filtrado
$termino = $_GET['termino'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// 3. Construir consulta base
$query = "SELECT 
            a.codigo, 
            a.nombre, 
            a.tipo, 
            DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y') as fecha_creacion,
            a.descripcion,
            CONCAT(
                e.nombre, ' -> Piso ', p.numero, 
                ' -> ', o.nombre, 
                ' -> Estante ', es.codigo,
                ' -> Cajón ', c.codigo
            ) AS ubicacion
          FROM archivos a
          JOIN archivo_ubicacion au ON a.id = au.archivo_id
          JOIN cajones c ON au.cajon_id = c.id
          JOIN estantes es ON c.estante_id = es.id
          JOIN oficinas o ON es.oficina_id = o.id
          JOIN pisos p ON o.piso_id = p.id
          JOIN edificios e ON p.edificio_id = e.id
          WHERE 1=1";

// 4. Aplicar filtros
$params = [];
$types = '';

if (!empty($termino)) {
    $query .= " AND (a.nombre LIKE ? OR a.codigo LIKE ? OR a.descripcion LIKE ?)";
    $params = array_merge($params, ["%$termino%", "%$termino%", "%$termino%"]);
    $types .= 'sss';
}

if (!empty($tipo)) {
    $query .= " AND a.tipo = ?";
    $params[] = $tipo;
    $types .= 's';
}

if (!empty($fecha_desde) && !empty($fecha_hasta)) {
    $query .= " AND a.fecha_creacion BETWEEN ? AND ?";
    $params[] = $fecha_desde;
    $params[] = $fecha_hasta;
    $types .= 'ss';
}

// 5. Ordenamiento por defecto
$query .= " ORDER BY a.fecha_creacion DESC";

// 6. Preparar y ejecutar consulta
$stmt = $conexion->prepare($query);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 7. Crear archivo CSV
$output = fopen('php://output', 'w');

// 8. Escribir encabezados
fputcsv($output, [
    'Código',
    'Nombre',
    'Tipo',
    'Fecha de Creación',
    'Descripción',
    'Ubicación Completa'
], ';');

// 9. Escribir datos con corrección de caracteres
while ($row = $result->fetch_assoc()) {
    // Convertir caracteres especiales para Excel
    $row = array_map(function($value) {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
    }, $row);
    
    fputcsv($output, $row, ';');
}

// 10. Cerrar recursos
fclose($output);
$stmt->close();
$conexion->close();
exit;