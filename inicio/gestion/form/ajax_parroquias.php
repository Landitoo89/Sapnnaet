<?php
require_once __DIR__ . '/../conexion/conexion_db.php';
$id_municipio = isset($_GET['id_municipio']) ? intval($_GET['id_municipio']) : 0;
$res = [];
if ($id_municipio > 0) {
    $stmt = $conn->prepare("SELECT id_parroquia, nombre FROM parroquias WHERE id_municipio = ? ORDER BY nombre ASC");
    $stmt->execute([$id_municipio]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
header('Content-Type: application/json');
echo json_encode($res);