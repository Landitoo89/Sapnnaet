<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$idPers = $_GET['id_pers'] ?? null;

if (!$idPers) {
    http_response_code(400);
    exit(json_encode(['error' => 'ID no proporcionado']));
}

try {
    $query = "
        SELECT 
            p.*,
            l.correo_institucional,
            d.nombre AS departamento,
            c.nombre AS cargo,
            TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
        FROM datos_personales p
        LEFT JOIN datos_laborales l ON p.id_pers = l.id_pers
        LEFT JOIN departamentos d ON l.id_departamento = d.id_departamento
        LEFT JOIN cargos c ON l.id_cargo = c.id_cargo
        WHERE p.id_pers = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idPers]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Empleado no encontrado']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>