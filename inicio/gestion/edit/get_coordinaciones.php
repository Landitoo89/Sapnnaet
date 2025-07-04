<?php
require_once __DIR__ . '/../conexion/conexion_db.php'; // Asegúrate de que esta conexión sea PDO

header('Content-Type: application/json'); // Indica que la respuesta será JSON

if (!isset($_GET['id_departamento']) || !is_numeric($_GET['id_departamento'])) {
    echo json_encode([]); // Devolver un array vacío si no hay ID válido
    exit;
}

$id_departamento = intval($_GET['id_departamento']);

try {
    $stmt = $conn->prepare("SELECT id_coordinacion, nombre FROM coordinaciones WHERE id_departamento = ? ORDER BY nombre ASC");
    $stmt->execute([$id_departamento]);
    $coordinaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($coordinaciones);
} catch (PDOException $e) {
    // En un entorno de producción, es mejor no mostrar el error completo
    // sino registrarlo y devolver un mensaje genérico.
    error_log("Error al cargar coordinaciones: " . $e->getMessage());
    echo json_encode([]);
}
?>