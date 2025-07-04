<?php
require 'conexion.php';

try {
    $stmt = $pdo->query("SELECT NOW() AS hora_servidor");
    $result = $stmt->fetch();
    echo "Conexión exitosa. Hora del servidor: " . $result['hora_servidor'];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>