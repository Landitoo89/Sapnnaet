<?php
session_start();

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generarToken() {
    return bin2hex(random_bytes(32));
}

function enviarEmail($destinatario, $asunto, $cuerpo) {
    // Implementar lógica de envío de emails (usar PHPMailer o similar)
    return true; // Temporal para pruebas
}

function protegerRuta($rolRequerido = null) {
    if (!isset($_SESSION['usuario'])) {
        header('Location: index.php');
        exit;
    }
    
    if ($rolRequerido && $_SESSION['usuario']['rol'] !== $rolRequerido) {
        header('Location: acceso_denegado.php');
        exit;
    }
}
?>