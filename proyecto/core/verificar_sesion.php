<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario'])) {
    // Registrar intento de acceso sin sesión
    if (file_exists('logger.php')) {
        require_once 'logger.php';
        log_session_event(0, 'access_denied', 'Intento de acceso sin sesión a: ' . $_SERVER['REQUEST_URI']);
    }
    
    //header('Location: /index.php?error=session_required');
    exit('Acceso denegado');
}

// Verificar inactividad (ejemplo: 30 minutos)
$inactivity = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity)) {
    require_once 'logger.php';
    log_session_event($_SESSION['id'], 'session_expired', 'Inactividad: ' . $_SERVER['REQUEST_URI']);
    
    session_unset();
    session_destroy();
    header('Location: ../index.php?error=inactivity');
    exit();
}

// Actualizar tiempo de última actividad
$_SESSION['last_activity'] = time();
?>