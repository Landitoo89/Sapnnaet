<?php
// core/logger.php

// Asegurarnos de incluir la conexión primero
//require '../conexion_archivero.php';

function log_session_event($user_id, $event_type, $details = '') {
    global $conexion;
    
    // Validar si la conexión está disponible
    if (!$conexion || $conexion->connect_error) {
        error_log("Error de conexión en logger: " . ($conexion->connect_error ?? 'Conexión no disponible'));
        return false;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $details = substr($details, 0, 500);  // Limitar longitud
    
    // Preparar la consulta
    $stmt = $conexion->prepare("INSERT INTO session_logs 
                               (user_id, event_type, ip_address, user_agent, details) 
                               VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
        return false;
    }
    
    // Vincular parámetros
    $stmt->bind_param("issss", $user_id, $event_type, $ip, $user_agent, $details);
    
    // Ejecutar
    if (!$stmt->execute()) {
        error_log("Error ejecutando log: " . $stmt->error);
        return false;
    }
    
    $stmt->close();
    return true;
}

function log_action(mysqli $conn, $user_id, string $eventType, string $details = ''): bool // <- Añadimos $conn y $user_id aquí
{
    // NO usamos global $conexion; aquí, usamos $conn que se pasa como argumento.
    
    // Validar si la conexión está disponible
    if (!$conn || $conn->connect_error) { // Usamos $conn en lugar de $conexion
        error_log("Error de conexión en log_action: " . ($conn->connect_error ?? 'Conexión no disponible'));
        return false;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A'; // Usar 'N/A' si no está definido
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $details = substr($details, 0, 500);  // Limitar longitud
    
    // Preparar la consulta
    $stmt = $conn->prepare("INSERT INTO action_logs 
                               (user_id, event_type, ip_address, user_agent, details) 
                               VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Error preparando consulta para action_logs: " . $conn->error); // Usamos $conn
        return false;
    }
    
    // Vincular parámetros
    $stmt->bind_param("issss", $user_id, $eventType, $ip, $user_agent, $details);
    
    // Ejecutar
    if (!$stmt->execute()) {
        error_log("Error ejecutando log en action_logs: " . $stmt->error);
        return false;
    }
    
    // Cerrar la declaración para liberar recursos
    $stmt->close();
    return true;
}

/**
 * Función para obtener el historial de logs de la tabla action_logs (o session_logs, dependiendo de tu tabla).
 * Si tu tabla es 'action_logs', esta función también debería usar 'action_logs'.
 *
 * @param mysqli $conn La conexión a la base de datos MySQLi.
 * @param int|null $user_id El ID del usuario para filtrar (null para todos).
 * @param int $limit El número máximo de registros a devolver.
 * @return array Un array de logs.
 */
function get_session_history(mysqli $conn, $user_id, $limit = 50): array // <- Añadimos $conn aquí
{
    // NO usamos global $conexion; aquí
    
    // Validar conexión
    if (!$conn || $conn->connect_error) { // Usamos $conn en lugar de $conexion
        error_log("Error de conexión en get_session_history: " . ($conn->connect_error ?? 'Conexión no disponible'));
        return [];
    }
    
    // Si user_id es null, obtener todos los logs
    if ($user_id === null) {
        $stmt = $conn->prepare("SELECT * FROM action_logs ORDER BY created_at DESC LIMIT ?"); // Asumiendo que la tabla de logs es 'action_logs'
        $stmt->bind_param("i", $limit);
    } else {
        $stmt = $conn->prepare("SELECT * FROM action_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"); // Asumiendo 'action_logs'
        $stmt->bind_param("ii", $user_id, $limit);
    }
    
    if (!$stmt) {
        error_log("Error preparando consulta de historial de logs: " . $conn->error); // Usamos $conn
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    return $logs;
}
?>