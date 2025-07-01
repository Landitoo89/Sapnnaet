<?php
require_once __DIR__ . '/conexion/conexion_db.php';

$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}
header('Content-Type: application/json');
echo json_encode($tables);
?>