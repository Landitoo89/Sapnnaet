<?php
session_start();
require_once 'conexion_archivero.php';

function log_session_event($user_id, $event_type, $details = '') {
    global $conexion;

    if (!$conexion || $conexion->connect_error) {
        // Error de conexión, registrar en el log del servidor
        error_log("Error de conexión en logger: " . ($conexion->connect_error ?? 'Conexión no disponible'));
        return false;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $details = substr($details, 0, 500);

    $stmt = $conexion->prepare("INSERT INTO session_logs 
        (user_id, event_type, ip_address, user_agent, details) 
        VALUES (?, ?, ?, ?, ?)");

    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
        return false;
    }

    $stmt->bind_param("issss", $user_id, $event_type, $ip, $user_agent, $details);

    if (!$stmt->execute()) {
        error_log("Error ejecutando log: " . $stmt->error);
        return false;
    }

    $stmt->close();
    return true;
}

if (isset($_SESSION['usuario']['id'])) {
    log_session_event($_SESSION['usuario']['id'], 'logout', 'Cierre de sesión manual');
}

session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: index.php');
exit;
?>