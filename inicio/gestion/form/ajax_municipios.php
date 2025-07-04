<?php
require_once __DIR__ . '/../conexion/conexion_db.php';
$id_estado = isset($_GET['id_estado']) ? intval($_GET['id_estado']) : 0;
$res = [];
if ($id_estado > 0) {
    $stmt = $conn->prepare("SELECT id_municipio, nombre FROM municipios WHERE id_estado = ? ORDER BY nombre ASC");
    $stmt->execute([$id_estado]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
header('Content-Type: application/json');
echo json_encode($res);